\name{concerto.test.get}
\alias{concerto.test.get}
\title{
Test object retrieval
}
\description{
Gets template object.
}
\usage{
concerto.test.get(testId)
}
\arguments{
  \item{testId}{
Test ID or name.
}
}
\details{
}
\value{
List with test object properties:
\item{id }{test object Id}
\item{name}{test object name}
\item{code}{test logic R code}
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
test1 <- concerto.test.get(1)
test2 <- concerto.test.get("some_other_test")
}

\keyword{ test }
