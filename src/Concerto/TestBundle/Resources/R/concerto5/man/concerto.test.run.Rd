\name{concerto.test.run}
\alias{concerto.test.run}
\title{
Running Concerto test
}
\description{
Runs Concerto test.
}
\usage{
concerto.test.run(testId, params = list())
}
\arguments{
  \item{testId}{
Test ID or name.
}
  \item{params}{
Named list of parameters you want to pass to the test logic.
}
  \item{mainTest}{
Logical. If TRUE will set record test final return variables in session.
}
}
\details{
}
\value{
Named list with the test specific return variables.
}
\references{
}
\author{
Przemyslaw Lis
}
\note{
}

\seealso{
}
\examples{
test <- concerto.test.run(1)
}
\keyword{ test }
